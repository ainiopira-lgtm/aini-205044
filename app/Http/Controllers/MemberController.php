<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMemberRequest;
use App\Http\Requests\UpdateMemberRequest;
use App\Models\Member;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class MemberController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);
        $search = trim($filters['q'] ?? '');
        $status = $filters['status'] ?? '';

        $members = Member::query()
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('membership_number', 'like', "%{$search}%")))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('members.index', compact('members', 'search', 'status'));
    }

    public function create(): View
    {
        return view('members.form', ['member' => null]);
    }

    public function store(StoreMemberRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $photo = $request->file('photo');
        unset($data['photo']);

        Role::findOrCreate('anggota', 'web');
        $storedPhoto = null;

        try {
            $member = DB::transaction(function () use ($data, $photo, &$storedPhoto): Member {
                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => Str::random(48),
                ]);
                $user->assignRole('anggota');

                $member = Member::create([
                    ...$data,
                    'user_id' => $user->id,
                    'membership_number' => $this->nextMembershipNumber(),
                    'joined_at' => today(),
                ]);

                if ($photo) {
                    $storedPhoto = $this->storePhoto($photo);
                    $member->update(['photo' => $storedPhoto]);
                }

                return $member;
            });
        } catch (Throwable $exception) {
            if ($storedPhoto) {
                Storage::disk('public')->delete($storedPhoto);
            }

            throw $exception;
        }

        return to_route('admin.members.show', $member)->with('success', 'Anggota berhasil ditambahkan.');
    }

    public function show(Member $member): View
    {
        $member->load(['user', 'loans.bookCopy.book', 'loans.fines']);
        $totalFine = $member->loans->sum(fn ($loan) => $loan->fines->sum('amount'));

        return view('members.show', compact('member', 'totalFine'));
    }

    public function edit(Member $member): View
    {
        return view('members.form', compact('member'));
    }

    public function update(UpdateMemberRequest $request, Member $member): RedirectResponse
    {
        $data = $request->validated();
        $photo = $request->file('photo');
        unset($data['photo']);
        $oldPhoto = $member->photo;
        $storedPhoto = null;

        try {
            DB::transaction(function () use ($member, $data, $photo, &$storedPhoto): void {
                $member->update([
                    ...$data,
                    ...($photo ? ['photo' => $storedPhoto = $this->storePhoto($photo)] : []),
                ]);

                $user = $member->user;
                if ($user) {
                    $user->update(['name' => $data['name'], 'email' => $data['email']]);
                } else {
                    Role::findOrCreate('anggota', 'web');
                    $user = User::create([
                        'name' => $data['name'],
                        'email' => $data['email'],
                        'password' => Str::random(48),
                    ]);
                    $user->assignRole('anggota');
                    $member->update(['user_id' => $user->id]);
                }
            });
        } catch (Throwable $exception) {
            if ($storedPhoto) {
                Storage::disk('public')->delete($storedPhoto);
            }

            throw $exception;
        }

        if ($photo && $oldPhoto) {
            Storage::disk('public')->delete($oldPhoto);
        }

        return to_route('admin.members.show', $member)->with('success', 'Data anggota berhasil diperbarui.');
    }

    public function destroy(Member $member): RedirectResponse
    {
        if ($member->loans()->exists()) {
            return back()->with('error', 'Anggota yang memiliki riwayat peminjaman tidak dapat dihapus.');
        }

        $photo = $member->photo;
        $user = $member->user;

        DB::transaction(function () use ($member, $user): void {
            $member->delete();
            $user?->delete();
        });

        if ($photo) {
            Storage::disk('public')->delete($photo);
        }

        return to_route('admin.members.index')->with('success', 'Anggota berhasil dihapus.');
    }

    public function card(Member $member): Response
    {
        $member->loadMissing('user');
        $photoDataUri = null;
        if ($member->photo) {
            $disk = Storage::disk('public');
            $photoDataUri = 'data:'.$disk->mimeType($member->photo).';base64,'.base64_encode($disk->get($member->photo));
        }

        $qrCode = base64_encode(QrCode::format('svg')->size(140)->generate($member->membership_number));

        return Pdf::loadView('members.card', compact('member', 'photoDataUri', 'qrCode'))
            ->setPaper([0, 0, 242.65, 153.07])
            ->download('kartu-'.$member->membership_number.'.pdf');
    }

    private function nextMembershipNumber(): string
    {
        $year = (int) now()->format('Y');
        DB::table('member_number_sequences')->insertOrIgnore([
            'year' => $year,
            'last_number' => 0,
        ]);

        $sequence = DB::table('member_number_sequences')
            ->where('year', $year)
            ->lockForUpdate()
            ->first();
        $nextNumber = $sequence->last_number + 1;

        DB::table('member_number_sequences')
            ->where('year', $year)
            ->update(['last_number' => $nextNumber]);

        return sprintf('AGT-%d-%04d', $year, $nextNumber);
    }

    private function storePhoto(UploadedFile $photo): string
    {
        $path = $photo->store('member-photos', 'public');
        if ($path === false) {
            throw new RuntimeException('Foto anggota gagal disimpan.');
        }

        return $path;
    }
}

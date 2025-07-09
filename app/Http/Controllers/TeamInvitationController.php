<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Laravel\Jetstream\Contracts\AddsTeamMembers;
use Laravel\Jetstream\Jetstream;

class TeamInvitationController extends Controller
{
    /**
     * Accept a team invitation.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $invitationId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function showAcceptPage($invitationId)
    {
        $invitation = Jetstream::teamInvitationModel()::whereKey($invitationId)->with('team')->firstOrFail();

        return Inertia::render('Auth/AcceptInvitation', [
            'invitation' => [
                'id' => $invitation->id,
                'email' => $invitation->email,
                'role' => $invitation->role,
                'team' => [
                    'name' => $invitation->team->name,
                ],
            ],
        ]);
        // return Inertia::render('Auth/AcceptInvitation', [
        //     'invitation' => [
        //         'id' => "",
        //         'email' => "",
        //         'role' => "",
        //         'team' => [
        //             'name' => "",
        //         ],
        //     ],
        // ]);
    }
    public function accept(Request $request, $invitationId)
    {
        Log::info('accepting...');
        $invitation = Jetstream::teamInvitationModel()::whereKey($invitationId)->firstOrFail();

        $request->validate([
            'name' => 'required|string|max:255',
            'password' => 'required|string|confirmed|min:8',
        ]);

        $user = User::where('email', $invitation->email)->first();

        if (!$user) {
            $user = User::create([
                'name' => $request->name,
                'email' => $invitation->email,
                'password' => Hash::make($request->password),
                'timezone' => 'Asia/Manila', // 👈 add this line
            ]);
            $user->sendEmailVerificationNotification();
        }

        Auth::login($user);

        app(AddsTeamMembers::class)->add(
            $invitation->team->owner,
            $invitation->team,
            $invitation->email,
            $invitation->role
        );

        $invitation->delete();

        Log::info('Accepted');

        return redirect()->intended(config('fortify.home'));
    }

    /**
     * Cancel the given team invitation.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $invitationId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request, $invitationId)
    {
        $model = Jetstream::teamInvitationModel();

        $invitation = $model::whereKey($invitationId)->firstOrFail();

        if (!Gate::forUser($request->user())->check('removeTeamMember', $invitation->team)) {
            throw new AuthorizationException;
        }

        $invitation->delete();

        return back(303);
    }
}

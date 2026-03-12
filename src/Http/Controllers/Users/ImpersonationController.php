<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Users;

use CraftCms\Cms\Auth\Impersonation;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Support\Flash;
use CraftCms\Cms\User\Actions\GetImpersonationUrlAction;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Users;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

use function CraftCms\Cms\t;

readonly class ImpersonationController
{
    use RespondsWithFlash;

    public function __construct(
        private Request $request,
        private Users $users,
        private Impersonation $impersonation,
    ) {}

    public function impersonate(): Response
    {
        $this->request->validate([
            'userId' => ['required', 'integer', Rule::exists(Table::USERS, 'id')],
        ]);

        $userId = $this->request->integer('userId');

        abort_if(
            is_null($user = $this->users->getUserById($userId)),
            400,
            "Invalid user ID: $userId",
        );

        $this->enforceImpersonatePermission($user);

        $this->impersonation->setImpersonatorId($this->request->user()->id);

        try {
            Auth::guard('craft')->login($user);
        } catch (Throwable) {
            Flash::fail(t('There was a problem impersonating this user.'));

            Log::error($this->request->user()->username.' tried to impersonate userId: '.$userId.' but something went wrong.');

            return back();
        }

        return $this->handleSuccessfulLogin($user);
    }

    public function getUrl(GetImpersonationUrlAction $getImpersonationUrlAction): JsonResponse
    {
        $this->request->validate([
            'userId' => ['required', 'integer', Rule::exists(Table::USERS, 'id')],
        ]);

        $userId = $this->request->integer('userId');

        abort_if(
            is_null($user = $this->users->getUserById($userId)),
            400,
            "Invalid user ID: $userId",
        );

        $this->enforceImpersonatePermission($user);

        $url = $getImpersonationUrlAction($user);

        abort_if($url === false, 500, 'Unable to generate impersonation URL.');

        return new JsonResponse(compact('url'));
    }

    public function withToken(): Response
    {
        $this->request->validate([
            'userId' => ['required', 'integer', Rule::exists(Table::USERS, 'id')],
            'prevUserId' => ['required', 'integer', Rule::exists(Table::USERS, 'id')],
        ]);

        $userId = $this->request->integer('userId');
        $prevUserId = $this->request->integer('prevUserId');

        /** @var User $user */
        $user = $this->users->getUserById($userId);

        $this->impersonation->setImpersonatorId($prevUserId);

        try {
            Auth::guard('craft')->login($user);
        } catch (Throwable) {
            Flash::fail(t('There was a problem impersonating this user.'));

            return back();
        }

        return $this->handleSuccessfulLogin($user);
    }

    private function handleSuccessfulLogin(User $user): Response
    {
        // Get the return URL
        $returnUrl = URL::returnUrl();

        // If this was an Ajax request, just return success:true
        if ($this->request->wantsJson()) {
            $return = [
                'returnUrl' => $returnUrl,
                'csrfTokenValue' => csrf_token(),
            ];

            return $this->asModelSuccess($user, modelName: 'user', data: $return);
        }

        return $this->redirectToPostedUrl($this->request->user(), $returnUrl);
    }

    private function enforceImpersonatePermission(User $user): void
    {
        $currentUser = $this->request->user();

        abort_unless(
            $this->users->canImpersonate($currentUser, $user),
            403,
            t('You do not have sufficient permissions to impersonate this user'),
        );
    }
}

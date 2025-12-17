<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Users;

use Craft;
use craft\base\Element;
use craft\helpers\UrlHelper;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Http\EnforcesPermissions;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Support\Utils;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Users;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

final readonly class UsersController
{
    use AuthorizesRequests;
    use EditUserTrait;
    use EnforcesPermissions;
    use RespondsWithFlash;

    public function index(Request $request, ?string $slug = null): View
    {
        $this->authorize('viewUsers');

        Edition::require(Edition::Team);

        return view('craftcms::users._index', [
            'title' => t('Users'),
            'buttonLabel' => mb_ucfirst(t('New {type}', [
                'type' => User::lowerDisplayName(),
            ])),
            'source' => $slug ?? $request->input('source'),
        ]);
    }

    public function create(Request $request, Drafts $drafts): Response
    {
        $user = new User;

        abort_unless(Craft::$app->getElements()->canSave($user), 403, 'User not authorized to save this user.');

        $user->setScenario(Element::SCENARIO_ESSENTIALS);
        if (! $drafts->saveElementAsDraft($user, $request->user()->id, markAsSaved: false)) {
            return $this->asModelFailure($user, mb_ucfirst(t('Couldn’t create {type}.', [
                'type' => User::lowerDisplayName(),
            ])), 'user');
        }

        $editUrl = $user->getCpEditUrl();

        if (! $request->wantsJson()) {
            return redirect(UrlHelper::urlWithParams($editUrl, [
                'fresh' => 1,
            ]));
        }

        return $this->asModelSuccess($user, t('{type} created.', [
            'type' => User::displayName(),
        ]), 'user', array_filter([
            'cpEditUrl' => $request->isCpRequest() ? $editUrl : null,
        ]));
    }

    public function edit(?int $userId = null): CpScreenResponse
    {
        $user = $this->editedUser($userId);

        /**
         * @TODO: Refactor away the runAction
         * let the elements/edit action do most of the work
         */
        Craft::$app->request->setIsCpRequest(true);
        $response = Craft::$app->runAction('elements/edit', [
            'element' => $user,
        ]);

        /**
         * This transforms the old Yii CpScreen to the new
         *
         * @var \craft\web\CpScreenResponseBehavior $cpScreen
         */
        $cpScreen = $response->getBehavior('cp-screen');
        $response = $this->asEditUserScreen($user, self::SCREEN_PROFILE);
        $reflection = new ReflectionClass($response);
        foreach (Utils::getPublicProperties($cpScreen) as $property => $value) {
            if (isset($response->{$property})) {
                continue;
            }

            try {
                $reflection->getProperty($property)->setValue($response, $value);
            } catch (ReflectionException) {
            }
        }

        return $response
            ->when(
                $user->getIsUnpublishedDraft() && $this->showPermissionsScreen(),
                function (CpScreenResponse $response) use ($user) {
                    $response
                        ->submitButtonLabel(t('Create and set permissions'))
                        ->redirectUrl($this->editUserScreenUrl($user, self::SCREEN_PERMISSIONS));
                },
            );
    }

    public function destroy(Request $request, Users $users): Response
    {
        $request->validate([
            'userId' => ['required', 'integer'],
        ]);

        $user = $users->getUserById($request->integer('userId'));

        abort_if(! $user, 400, 'User not found');

        if (! $user->getIsCurrent()) {
            $this->authorize('deleteUsers');

            if ($user->admin) {
                $this->requireAdmin();
            }
        }

        // Are we transferring the user’s content to a different user?
        $transferContentToId = $request->input('transferContentTo');

        if (is_array($transferContentToId) && isset($transferContentToId[0])) {
            $transferContentToId = $transferContentToId[0];
        }

        if ($transferContentToId) {
            $transferContentTo = $users->getUserById((int) $transferContentToId);

            abort_if(! $transferContentTo, 400, 'User not found');
        } else {
            $transferContentTo = null;
        }

        // Delete the user
        $user->inheritorOnDelete = $transferContentTo;

        if (! Craft::$app->getElements()->deleteElement($user)) {
            return $this->asFailure(t('Couldn’t delete {type}.', [
                'type' => User::lowerDisplayName(),
            ]));
        }

        return $this->asSuccess(t('{type} deleted.', [
            'type' => User::displayName(),
        ]));
    }
}

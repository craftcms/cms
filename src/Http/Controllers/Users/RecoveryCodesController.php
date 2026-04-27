<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Users;

use CraftCms\Cms\Auth\Auth;
use CraftCms\Cms\Auth\Concerns\ConfirmsPasswords;
use CraftCms\Cms\Auth\Methods\RecoveryCodes;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Translation\I18N;
use CraftCms\Cms\Translation\Locale;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

readonly class RecoveryCodesController
{
    use ConfirmsPasswords;
    use RespondsWithFlash;

    public function __construct(
        private Auth $auth
    ) {}

    public function generate(): Response
    {
        $this->requireConfirmedPassword();

        /** @var RecoveryCodes $recoveryCodes */
        $recoveryCodes = $this->auth->getMethod(RecoveryCodes::class);
        $codes = $recoveryCodes->generateRecoveryCodes();

        return $this->asSuccess(t('Recovery codes generated.'), [
            'codes' => $codes,
        ]);
    }

    public function download(Request $request, Sites $sites, I18N $i18N): Response
    {
        $this->requireConfirmedPassword();

        /** @var RecoveryCodes $recoveryCodes */
        $recoveryCodes = $this->auth->getMethod(RecoveryCodes::class);
        [$codes, $dateCreated] = $recoveryCodes->getRecoveryCodes();

        abort_if(empty($codes), 400, 'No recovery codes exist for this user.');

        $systemName = t(Cms::systemName(), [], 'site');
        $systemNameUnderline = str_repeat('=', mb_strlen($systemName));
        $primarySite = $sites->getPrimarySite();
        $website = $primarySite->getBaseUrl() ?? $primarySite->getName();
        $user = $request->user();
        $generalConfig = Cms::config();
        $username = ! $generalConfig->useEmailAsUsername && $user->username ? $user->username : null;
        $account = $username ? sprintf('%s (%s)', $username, $user->email) : $user->email;
        $generated = $i18N->getFormatter()->asDate($dateCreated, Locale::LENGTH_SHORT);
        $codeContent = implode('', array_map(
            fn (string $code) => $code ? "- $code\n" : "- ~~~~~~~~~~~~~\n",
            $codes,
        ));

        $content = <<<EOD
        Recovery Codes for $systemName
        ===================$systemNameUnderline
        
        These codes can be used as a backup form of verification, when you’re unable to
        use your primary two-step verification method(s).
        
        Each code can only be used once. Store them in a safe place!
        
        Website:   $website
        Account:   $account
        Generated: $generated
        
        $codeContent
        EOD;

        $name = sprintf('%s recovery codes - %s.txt', $systemName, $username ?? $user->email);

        return response()->make($content, 200, [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => 'attachment; filename="'.$name.'"',
        ]);
    }
}

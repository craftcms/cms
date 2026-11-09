<?php

declare(strict_types=1);

use craft\mail\transportadapters\BaseTransportAdapter;
use craft\mail\transportadapters\Sendmail;
use craft\mail\transportadapters\Smtp;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Yii2Adapter\Mail\LegacyTransportCompatibility;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mailer\Transport\SendmailTransport;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;

beforeEach(function() {
    $this->mailMailer = getenv('MAIL_MAILER');
    putenv('MAIL_MAILER');
    unset($_ENV['MAIL_MAILER'], $_SERVER['MAIL_MAILER']);
    Config::set('mail.default', 'log');
    RecordingTransport::$sent = [];
});

afterEach(function() {
    putenv("MAIL_MAILER=$this->mailMailer");
    $_ENV['MAIL_MAILER'] = $_SERVER['MAIL_MAILER'] = $this->mailMailer;
});

function saveLegacyTransport(string $type, array $settings, array $email = []): void
{
    ProjectConfig::set('email', array_merge(ProjectConfig::get('email') ?? [], [
        'transportType' => $type,
        'transportSettings' => $settings,
    ], $email));

    new LegacyTransportCompatibility()->register(app());
}

it('sends email through a plugin transport adapter saved in the project config', function() {
    saveLegacyTransport(PluginTransportAdapter::class, ['apiKey' => 'secret-key']);

    Mail::raw('Hello', fn($message) => $message->to('to@example.com')->subject('Legacy transport'));

    expect(Config::get('mail.default'))->toBe(LegacyTransportCompatibility::MAILER)
        ->and(RecordingTransport::$sent)->toHaveCount(1)
        ->and(RecordingTransport::$sent[0]['apiKey'])->toBe('secret-key')
        ->and(RecordingTransport::$sent[0]['subject'])->toBe('Legacy transport');
});

it('creates transports from built-in transport adapter settings', function(string $type, array $settings, string $transportClass, string $dsn) {
    saveLegacyTransport($type, $settings);

    $transport = Mail::mailer(LegacyTransportCompatibility::MAILER)->getSymfonyTransport();

    expect($transport)->toBeInstanceOf($transportClass)
        ->and((string)$transport)->toBe($dsn);
})->with([
    'smtp' => [Smtp::class, ['host' => 'mail.example.test', 'port' => '2526'], EsmtpTransport::class, 'smtp://mail.example.test:2526'],
    'sendmail' => [Sendmail::class, ['command' => '/usr/sbin/sendmail -t -i'], SendmailTransport::class, 'smtp://sendmail'],
]);

it('keeps the default mailer once a Laravel mailer is configured', function(Closure $configure, array $email) {
    $configure();
    $default = Config::get('mail.default');

    saveLegacyTransport(PluginTransportAdapter::class, ['apiKey' => 'secret-key'], $email);

    expect(Config::get('mail.default'))->toBe($default)
        ->and(Config::get('mail.mailers.' . LegacyTransportCompatibility::MAILER))->not->toBeNull();
})->with([
    'mailer chosen in the email settings' => [fn() => null, ['mailer' => 'array']],
    'MAIL_MAILER set' => [fn() => putenv('MAIL_MAILER=log'), []],
    'default mailer changed in config/mail.php' => [fn() => Config::set('mail.default', 'array'), []],
]);

class PluginTransportAdapter extends BaseTransportAdapter
{
    public string $apiKey = '';

    public static function displayName(): string
    {
        return 'Plugin transport';
    }

    public function defineTransport(): array|AbstractTransport
    {
        return new RecordingTransport($this->apiKey);
    }
}

class RecordingTransport extends AbstractTransport
{
    public static array $sent = [];

    public function __construct(private readonly string $apiKey)
    {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        self::$sent[] = [
            'apiKey' => $this->apiKey,
            'subject' => $message->getOriginalMessage()->getSubject(),
        ];
    }

    public function __toString(): string
    {
        return 'recording://default';
    }
}

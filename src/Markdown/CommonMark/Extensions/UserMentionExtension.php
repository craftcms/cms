<?php

declare(strict_types=1);

namespace CraftCms\Cms\Markdown\CommonMark\Extensions;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\Extension\Mention\Mention;

class UserMentionExtension implements ExtensionInterface
{
    private const string URL_PREFIX = 'craft-user:';

    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment->addEventListener(DocumentParsedEvent::class, $this(...));
    }

    public function __invoke(DocumentParsedEvent $event): void
    {
        foreach ($event->getDocument()->iterator() as $node) {
            if (! $node instanceof Link || ! str_starts_with($node->getUrl(), self::URL_PREFIX)) {
                continue;
            }

            $mention = new Mention('user', '@', substr($node->getUrl(), strlen(self::URL_PREFIX)));
            $mention->setUrl($node->getUrl());
            $mention->setTitle($node->getTitle());
            $mention->replaceChildren($node->children());
            $node->replaceWith($mention);
        }
    }
}

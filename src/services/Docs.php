<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\events\RegisterComponentTypesEvent;
use craft\helpers\Html;
use craft\helpers\Json;
use yii\base\Component;

/**
 * Access and display official documentation resources, programmatically.
 *
 * URLs for resources can be overridden via application config, for local development or outright replacement.
 */
class Docs extends Component
{
    /**
     * Base URL for developer documentation pages.
     */
    public string $documentationBaseUrl = 'https://craftcms.com/docs/';

    /**
     * Base URL for documentation-related APIs.
     */
    public string $docsApiBaseUrl = 'https://craftcms.com/api/docs';

    /**
     * Base URL for knowledge base articles.
     */
    public string $kbBaseUrl = 'https://craftcms.com/knowledge-base/';

    /**
     * Base URL for class Reference.
     */
    public string $classReferenceBaseUrl = 'https://docs.craftcms.com/';

    /**
     * @event RegisterComponentTypes Emitted as the system builds its list of candidates for resolving a class’s class reference/documentation URL. Prepend additional resolvers with more stringent matching criteria!
     */
    const EVENT_DEFINE_CLASS_REFERENCE_RESOLVERS = 'defineBaseClassReferenceResolvers';

    /**
     * Sends a query to the docs API and returns the decoded response.
     *
     * @param string $resource API path.
     * @param array $params Query params to send with the request.
     * @return array Decoded JSON response object.
     */
    public function makeApiRequest(string $resource, array $params = []): array
    {
        $client = Craft::createGuzzleClient([
            'base_uri' => $this->docsApiBaseUrl,
        ]);

        $response = $client->get($resource, [
            'query' => $params,
        ]);

        return Json::decodeIfJson($response->getBody());
    }

    /**
     * Builds a URL to a page within the official documentation.
     *
     * @param string $path Page path. This should include any extension or suffix!
     * @return string Absolute URL
     */
    public function docsUrl(string $path = ''): string
    {
        return $this->documentationBaseUrl . trim($path, '/');
    }

    /**
     * Builds a URL to a Knowledge Base article or category.
     *
     * @param string $path Category or article slug.
     * @return string Absolute URL
     */
    public function kbUrl(string $path = ''): string
    {
        return $this->kbBaseUrl . trim($path, '/');
    }

    /**
     * Generates a URL to the class reference page for the passed class or object.
     *
     * @param mixed $source
     * @param string $member
     * @param string $memberType
     * @return string|null URL or null when one couldn’t be a resolved.
     */
    public function classReferenceUrl(mixed $source = null, string $member = null, string $memberType = null): string|null
    {
        $url = $this->classReferenceBaseUrl;

        // Do you just want the bare URL? Sure:
        if ($source === null) {
            return $url;
        }

        // Normalize into a fully-qualified class name:
        if (is_object($source)) {
            $source = $source::class;
        }

        foreach ($this->getResolvers() as $resolver) {
            if (!$resolver::match($source)) {
                continue;
            }

            return $resolver::getUrl($source, $member, $memberType);
        }

        // Didn't find a resolver? That’s OK, this should be a signal to the caller that a link is not suitable to output:
        return null;
    }

    /**
     * Builds an HTML anchor tag linking to the class reference page for the provided type/class.
     *
     * @param string $className
     * @return string Markup
     */
    public function classReferenceLink(string $className): string
    {
        return Html::a(Html::tag('code', $className), $this->classReferenceUrl($className), [
            'target' => '_blank',
        ]);
    }

    /**
     * Turns a fully-qualified class name into a valid class reference URL segment.
     *
     * This output agrees with internal logic for generating document names.
     *
     * @param string $className
     * @return string Kebab-cased class name
     */
    public function getClassHandle(string $className): string
    {
        return strtolower(str_replace('\\', '-', $className));
    }

    /**
     * Returns a list of native + plugin-provided class reference resolvers.
     *
     * The order of these resolvers matters! Entries with relaxed matching criteria will swallow more specific matches if specified earlier.
     *
     * @return BaseResolver[]
     */
    private function getResolvers(): array
    {
        $resolvers = [
            \craft\docs\resolvers\Commerce::class,
            \craft\docs\resolvers\Cms::class,
            \craft\docs\resolvers\Yii::class,
            \craft\docs\resolvers\Php::class,
        ];

        $event = new RegisterComponentTypesEvent([
            'types' => $resolvers,
        ]);

        $this->trigger(self::EVENT_DEFINE_CLASS_REFERENCE_RESOLVERS, $event);

        return $event->types;
    }
}

<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\db;

/**
 * This class provides constants for defining Craft’s database table names.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.1.0
 */
abstract class Table
{
    /** @since 4.0.0 */
    public const ADDRESSES = '{{%addresses}}';
    /** @since 3.7.0 */
    public const ANNOUNCEMENTS = '{{%announcements}}';
    public const ASSETINDEXDATA = '{{%assetindexdata}}';
    /** @since 4.0.0 */
    public const ASSETINDEXINGSESSIONS = '{{%assetindexingsessions}}';
    public const ASSETS = '{{%assets}}';
    public const ASSETS_SITES = '{{%assets_sites}}';
    /** @since 5.0 */
    public const AUTHENTICATOR = '{{%authenticator}}';
    /** @since 5.7.0 */
    public const BULKOPEVENTS = '{{%bulkopevents}}';
    /** @since 3.4.14 */
    public const CACHE = '{{%cache}}';
    public const CATEGORIES = '{{%categories}}';
    public const CATEGORYGROUPS = '{{%categorygroups}}';
    public const CATEGORYGROUPS_SITES = '{{%categorygroups_sites}}';
    /** @since 3.4.0 */
    public const CHANGEDATTRIBUTES = '{{%changedattributes}}';
    /** @since 3.4.0 */
    public const CHANGEDFIELDS = '{{%changedfields}}';
    /** @since 5.8.0 */
    public const CONTENTBLOCKS = '{{%contentblocks}}';
    public const CRAFTIDTOKENS = '{{%craftidtokens}}';
    public const DEPRECATIONERRORS = '{{%deprecationerrors}}';
    /** @since 3.2.0 */
    public const DRAFTS = '{{%drafts}}';
    /** @since 4.5.0 */
    public const ELEMENTACTIVITY = '{{%elementactivity}}';
    public const ELEMENTS = '{{%elements}}';
    /** @since 5.0.0 */
    public const ELEMENTS_BULKOPS = '{{%elements_bulkops}}';
    /** @since 5.0.0 */
    public const ELEMENTS_OWNERS = '{{%elements_owners}}';
    public const ELEMENTS_SITES = '{{%elements_sites}}';
    public const ENTRIES = '{{%entries}}';
    /** @since 5.0.0 */
    public const ENTRIES_AUTHORS = '{{%entries_authors}}';
    public const ENTRYTYPES = '{{%entrytypes}}';
    public const FIELDLAYOUTS = '{{%fieldlayouts}}';
    public const FIELDS = '{{%fields}}';
    public const GLOBALSETS = '{{%globalsets}}';
    /** @since 3.3.0 */
    public const GQLSCHEMAS = '{{%gqlschemas}}';
    /** @since 3.4.0 */
    public const GQLTOKENS = '{{%gqltokens}}';
    /** @since 4.0.0 */
    public const IMAGETRANSFORMINDEX = '{{%imagetransformindex}}';
    /** @since 4.0.0 */
    public const IMAGETRANSFORMS = '{{%imagetransforms}}';
    public const INFO = '{{%info}}';
    public const MIGRATIONS = '{{%migrations}}';
    /** @since 3.4.0 */
    public const PHPSESSIONS = '{{%phpsessions}}';
    public const PLUGINS = '{{%plugins}}';
    /** @since 3.4.0 */
    public const PROJECTCONFIG = '{{%projectconfig}}';
    public const QUEUE = '{{%queue}}';
    /** @since 5.0 */
    public const RECOVERYCODES = '{{%recoverycodes}}';
    public const RELATIONS = '{{%relations}}';
    public const RESOURCEPATHS = '{{%resourcepaths}}';
    /** @since 3.2.0 */
    public const REVISIONS = '{{%revisions}}';
    public const SEARCHINDEX = '{{%searchindex}}';
    /** @since 5.7.0 */
    public const SEARCHINDEXQUEUE = '{{%searchindexqueue}}';
    /** @since 5.7.0 */
    public const SEARCHINDEXQUEUE_FIELDS = '{{%searchindexqueue_fields}}';
    public const SECTIONS = '{{%sections}}';
    /** @since 5.0.0 */
    public const SECTIONS_ENTRYTYPES = '{{%sections_entrytypes}}';
    public const SECTIONS_SITES = '{{%sections_sites}}';
    public const SEQUENCES = '{{%sequences}}';
    public const SESSIONS = '{{%sessions}}';
    public const SHUNNEDMESSAGES = '{{%shunnedmessages}}';
    public const SITEGROUPS = '{{%sitegroups}}';
    public const SITES = '{{%sites}}';
    /** @since 5.3.0 */
    public const SSO_IDENTITIES = '{{%sso_identities}}';
    public const STRUCTUREELEMENTS = '{{%structureelements}}';
    public const STRUCTURES = '{{%structures}}';
    public const SYSTEMMESSAGES = '{{%systemmessages}}';
    public const TAGGROUPS = '{{%taggroups}}';
    public const TAGS = '{{%tags}}';
    public const TOKENS = '{{%tokens}}';
    public const USERGROUPS = '{{%usergroups}}';
    public const USERGROUPS_USERS = '{{%usergroups_users}}';
    public const USERPERMISSIONS = '{{%userpermissions}}';
    public const USERPERMISSIONS_USERGROUPS = '{{%userpermissions_usergroups}}';
    public const USERPERMISSIONS_USERS = '{{%userpermissions_users}}';
    public const USERPREFERENCES = '{{%userpreferences}}';
    public const USERS = '{{%users}}';
    public const VOLUMEFOLDERS = '{{%volumefolders}}';
    public const VOLUMES = '{{%volumes}}';
    /** @since 5.0 */
    public const WEBAUTHN = '{{%webauthn}}';
    public const WIDGETS = '{{%widgets}}';
}

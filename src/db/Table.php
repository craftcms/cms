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
    /** @since 4.0.0 */
    public const IMAGETRANSFORMINDEX = '{{%imagetransformindex}}';
    /** @since 4.0.0 */
    public const IMAGETRANSFORMS = '{{%imagetransforms}}';
    /** @since 3.4.14 */
    public const CACHE = '{{%cache}}';
    public const CATEGORIES = '{{%categories}}';
    public const CATEGORYGROUPS = '{{%categorygroups}}';
    public const CATEGORYGROUPS_SITES = '{{%categorygroups_sites}}';
    /** @since 3.4.0 */
    public const CHANGEDATTRIBUTES = '{{%changedattributes}}';
    /** @since 3.4.0 */
    public const CHANGEDFIELDS = '{{%changedfields}}';
    public const CONTENT = '{{%content}}';
    public const CRAFTIDTOKENS = '{{%craftidtokens}}';
    public const DEPRECATIONERRORS = '{{%deprecationerrors}}';
    /** @since 3.2.0 */
    public const DRAFTS = '{{%drafts}}';
    public const ELEMENTS = '{{%elements}}';
    public const ELEMENTS_SITES = '{{%elements_sites}}';
    public const RESOURCEPATHS = '{{%resourcepaths}}';
    /** @since 3.2.0 */
    public const REVISIONS = '{{%revisions}}';
    public const SEQUENCES = '{{%sequences}}';
    public const SYSTEMMESSAGES = '{{%systemmessages}}';
    public const ENTRIES = '{{%entries}}';
    public const ENTRYTYPES = '{{%entrytypes}}';
    public const FIELDGROUPS = '{{%fieldgroups}}';
    public const FIELDLAYOUTFIELDS = '{{%fieldlayoutfields}}';
    public const FIELDLAYOUTS = '{{%fieldlayouts}}';
    public const FIELDLAYOUTTABS = '{{%fieldlayouttabs}}';
    public const FIELDS = '{{%fields}}';
    public const GLOBALSETS = '{{%globalsets}}';
    /** @since 3.3.0 */
    public const GQLSCHEMAS = '{{%gqlschemas}}';
    /** @since 3.4.0 */
    public const GQLTOKENS = '{{%gqltokens}}';
    public const INFO = '{{%info}}';
    public const MATRIXBLOCKS = '{{%matrixblocks}}';
    /** @since 4.0.0 */
    public const MATRIXBLOCKS_OWNERS = '{{%matrixblocks_owners}}';
    public const MATRIXBLOCKTYPES = '{{%matrixblocktypes}}';
    public const MIGRATIONS = '{{%migrations}}';
    /** @since 3.4.0 */
    public const PHPSESSIONS = '{{%phpsessions}}';
    public const PLUGINS = '{{%plugins}}';
    /** @since 3.4.0 */
    public const PROJECTCONFIG = '{{%projectconfig}}';
    public const QUEUE = '{{%queue}}';
    public const RELATIONS = '{{%relations}}';
    public const SECTIONS = '{{%sections}}';
    public const SECTIONS_SITES = '{{%sections_sites}}';
    public const SESSIONS = '{{%sessions}}';
    public const SHUNNEDMESSAGES = '{{%shunnedmessages}}';
    public const SITES = '{{%sites}}';
    public const SITEGROUPS = '{{%sitegroups}}';
    public const STRUCTUREELEMENTS = '{{%structureelements}}';
    public const STRUCTURES = '{{%structures}}';
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
    public const WIDGETS = '{{%widgets}}';
    public const SEARCHINDEX = '{{%searchindex}}';
}

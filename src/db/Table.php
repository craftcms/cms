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
    const ASSETINDEXDATA = '{{%assetindexdata}}';
    const ASSETS = '{{%assets}}';
    const ASSETTRANSFORMINDEX = '{{%assettransformindex}}';
    const ASSETTRANSFORMS = '{{%assettransforms}}';
    /** @since 3.4.14 */
    const CACHE = '{{%cache}}';
    const CATEGORIES = '{{%categories}}';
    const CATEGORYGROUPS = '{{%categorygroups}}';
    const CATEGORYGROUPS_SITES = '{{%categorygroups_sites}}';
    /** @since 3.4.0 */
    const CHANGEDATTRIBUTES = '{{%changedattributes}}';
    /** @since 3.4.0 */
    const CHANGEDFIELDS = '{{%changedfields}}';
    const CONTENT = '{{%content}}';
    const CRAFTIDTOKENS = '{{%craftidtokens}}';
    const DEPRECATIONERRORS = '{{%deprecationerrors}}';
    /** @since 3.2.0 */
    const DRAFTS = '{{%drafts}}';
    const ELEMENTINDEXSETTINGS = '{{%elementindexsettings}}';
    const ELEMENTS = '{{%elements}}';
    const ELEMENTS_SITES = '{{%elements_sites}}';
    const RESOURCEPATHS = '{{%resourcepaths}}';
    /** @since 3.2.0 */
    const REVISIONS = '{{%revisions}}';
    const SEQUENCES = '{{%sequences}}';
    const SYSTEMMESSAGES = '{{%systemmessages}}';
    const ENTRIES = '{{%entries}}';
    /** @deprecated in 3.2.0 */
    const ENTRYDRAFTS = '{{%entrydrafts}}';
    const ENTRYTYPES = '{{%entrytypes}}';
    /** @deprecated in 3.2.0 */
    const ENTRYVERSIONS = '{{%entryversions}}';
    const FIELDGROUPS = '{{%fieldgroups}}';
    const FIELDLAYOUTFIELDS = '{{%fieldlayoutfields}}';
    const FIELDLAYOUTS = '{{%fieldlayouts}}';
    const FIELDLAYOUTTABS = '{{%fieldlayouttabs}}';
    const FIELDS = '{{%fields}}';
    const GLOBALSETS = '{{%globalsets}}';
    /** @since 3.3.0 */
    const GQLSCHEMAS = '{{%gqlschemas}}';
    /** @since 3.4.0 */
    const GQLTOKENS = '{{%gqltokens}}';
    const INFO = '{{%info}}';
    const MATRIXBLOCKS = '{{%matrixblocks}}';
    const MATRIXBLOCKTYPES = '{{%matrixblocktypes}}';
    const MIGRATIONS = '{{%migrations}}';
    /** @since 3.4.0 */
    const PHPSESSIONS = '{{%phpsessions}}';
    const PLUGINS = '{{%plugins}}';
    /** @since 3.4.0 */
    const PROJECTCONFIG = '{{%projectconfig}}';
    const QUEUE = '{{%queue}}';
    const RELATIONS = '{{%relations}}';
    const SECTIONS = '{{%sections}}';
    const SECTIONS_SITES = '{{%sections_sites}}';
    const SESSIONS = '{{%sessions}}';
    const SHUNNEDMESSAGES = '{{%shunnedmessages}}';
    const SITES = '{{%sites}}';
    const SITEGROUPS = '{{%sitegroups}}';
    const STRUCTUREELEMENTS = '{{%structureelements}}';
    const STRUCTURES = '{{%structures}}';
    const TAGGROUPS = '{{%taggroups}}';
    const TAGS = '{{%tags}}';
    const TEMPLATECACHEELEMENTS = '{{%templatecacheelements}}';
    const TEMPLATECACHEQUERIES = '{{%templatecachequeries}}';
    const TEMPLATECACHES = '{{%templatecaches}}';
    const TOKENS = '{{%tokens}}';
    const USERGROUPS = '{{%usergroups}}';
    const USERGROUPS_USERS = '{{%usergroups_users}}';
    const USERPERMISSIONS = '{{%userpermissions}}';
    const USERPERMISSIONS_USERGROUPS = '{{%userpermissions_usergroups}}';
    const USERPERMISSIONS_USERS = '{{%userpermissions_users}}';
    const USERPREFERENCES = '{{%userpreferences}}';
    const USERS = '{{%users}}';
    const VOLUMEFOLDERS = '{{%volumefolders}}';
    const VOLUMES = '{{%volumes}}';
    const WIDGETS = '{{%widgets}}';
    const SEARCHINDEX = '{{%searchindex}}';
}

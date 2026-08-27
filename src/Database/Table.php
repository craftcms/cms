<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database;

/**
 * This class provides constants for defining Craft’s database table names.
 */
readonly class Table
{
    public const string ACTIVITYEVENTS = 'activityevents';

    public const string ADDRESSES = 'addresses';

    public const string ASSETINDEXDATA = 'assetindexdata';

    public const string ASSETINDEXINGSESSIONS = 'assetindexingsessions';

    public const string ASSETS = 'assets';

    public const string ASSETS_SITES = 'assets_sites';

    public const string AUTHENTICATOR = 'authenticator';

    public const string BULKOPEVENTS = 'bulkopevents';

    public const string CACHE = 'cache';

    public const string CHANGEDATTRIBUTES = 'changedattributes';

    public const string CHANGEDFIELDS = 'changedfields';

    public const string CONTENTBLOCKS = 'contentblocks';

    public const string DEPRECATIONERRORS = 'deprecationerrors';

    public const string DRAFTS = 'drafts';

    public const string ELEMENTACTIVITY = 'elementactivity';

    public const string ELEMENTS = 'elements';

    public const string ELEMENTS_BULKOPS = 'elements_bulkops';

    public const string ELEMENTS_OWNERS = 'elements_owners';

    public const string ELEMENTS_SITES = 'elements_sites';

    public const string ENTRIES = 'entries';

    public const string ENTRIES_AUTHORS = 'entries_authors';

    public const string ENTRYTYPES = 'entrytypes';

    public const string FIELDLAYOUTS = 'fieldlayouts';

    public const string FIELDREFERENCES = 'fieldreferences';

    public const string FIELDS = 'fields';

    public const string GQLSCHEMAS = 'gqlschemas';

    public const string GQLTOKENS = 'gqltokens';

    public const string IMAGETRANSFORMINDEX = 'imagetransformindex';

    public const string IMAGETRANSFORMS = 'imagetransforms';

    public const string INFO = 'info';

    public const string MIGRATIONS = 'migrations';

    public const string PASSWORD_RESET_TOKENS = 'password_reset_tokens';

    public const string PHPSESSIONS = 'sessions';

    public const string PLUGINS = 'plugins';

    public const string PROJECTCONFIG = 'projectconfig';

    public const string JOBPROGRESS = 'jobprogress';

    #[\Deprecated(message: "in 6.0.0. The queue table has been removed. Use Laravel's queue system instead.")]
    public const string QUEUE = 'queue';

    public const string RECOVERYCODES = 'recoverycodes';

    public const string RELATIONS = 'relations';

    public const string RESOURCEPATHS = 'resourcepaths';

    public const string REVISIONS = 'revisions';

    public const string ROUTETOKENS = 'routetokens';

    public const string SEARCHINDEX = 'searchindex';

    public const string SEARCHINDEXQUEUE = 'searchindexqueue';

    public const string SEARCHINDEXQUEUE_FIELDS = 'searchindexqueue_fields';

    public const string SECTIONS = 'sections';

    public const string SECTIONS_ENTRYTYPES = 'sections_entrytypes';

    public const string SECTIONS_SITES = 'sections_sites';

    public const string SEQUENCES = 'sequences';

    public const string SESSIONS = 'sessions';

    public const string SHUNNEDMESSAGES = 'shunnedmessages';

    public const string SITEGROUPS = 'sitegroups';

    public const string SITES = 'sites';

    public const string SSO_IDENTITIES = 'sso_identities';

    public const string STRUCTUREELEMENTS = 'structureelements';

    public const string STRUCTURES = 'structures';

    public const string SYSTEMMESSAGES = 'systemmessages';

    public const string USERGROUPS = 'usergroups';

    public const string USERGROUPS_USERS = 'usergroups_users';

    public const string USERPERMISSIONS = 'userpermissions';

    public const string USERPERMISSIONS_USERGROUPS = 'userpermissions_usergroups';

    public const string USERPERMISSIONS_USERS = 'userpermissions_users';

    public const string USERPREFERENCES = 'userpreferences';

    public const string USERS = 'users';

    public const string VOLUMEFOLDERS = 'volumefolders';

    public const string VOLUMES = 'volumes';

    public const string WEBAUTHN = 'webauthn';

    public const string WIDGETS = 'widgets';
}

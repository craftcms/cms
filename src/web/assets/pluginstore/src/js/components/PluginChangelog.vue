<template>
    <div class="plugin-changelog">
        <div class="tw-flex">
            <h2 class="flex-1">Changelog</h2>
            <div>
                <a v-if="showMore === false" @click.prevent="showMore = true">More</a>
                <a v-if="showMore === true" @click.prevent="showMore = false">Less</a>
            </div>
        </div>

        <div class="releases">
            <changelog-release version="2.3.3" date="2018-09-17">
                <h3>Fixed</h3>
                <ul>
                    <li>Fixed a bug where Payments were not working correctly in
                        Flexbox and Grid formatting templates and demo templates.
                    </li>
                </ul>
            </changelog-release>

            <template v-if="showMore">
                <changelog-release version="2.3.2" date="2018-09-14">
                    <h3>Fixed</h3>
                    <ul>
                        <li>Various bug fixes for Freeform Payments feature.</li>
                        <li>Bug fixes and improvements to demo and sample templates to
                            better accommodate Freeform Payments.
                        </li>
                    </ul>
                </changelog-release>

                <changelog-release version="2.3.1" date="2018-09-13">
                    <h3>Changed</h3>
                    <ul>
                        <li>Updated reCAPTCHA field to only be allowed once per form
                            (disappears from Special fields list when in use).
                        </li>
                        <li>Various tweaks and improvements for Freeform Payments
                            compatibility.
                        </li>
                    </ul>
                </changelog-release>

                <changelog-release version="2.3.0" date="2018-09-12">
                    <h3>Added</h3>
                    <ul>
                        <li>Added ability to set Mailing List fields as hidden fields
                            (automatically opting in users).
                        </li>
                    </ul>

                    <h3>Changed</h3>
                    <ul>
                        <li>Updated for compatibility with future Freeform Payments
                            add-on plugin.
                        </li>
                        <li>Number fieldtype is no longer Pro only, and part of Lite
                            edition (in consideration for Freeform Payments plugin).
                        </li>
                        <li>Updated CP single submission view to include a note for
                            Mailing List fields that mentions data is not stored for
                            this field type.
                        </li>
                        <li>Updated new field creation to error if maximum number of
                            fields are reached.
                        </li>
                        <li>Updated Freeform's automatically inserted JS to no longer
                            include `type="text/javascript"`.
                        </li>
                        <li>Updated reCAPTCHA settings to be stored in Freeform Lite
                            rather than Pro.
                        </li>
                        <li>Updated Element Connections feature to only attempt to fire
                            when it's properly set up (to eliminate form errors if
                            accidentally partially added).
                        </li>
                        <li>Improved AJAX script in Demo Templates to better handle
                            script loading and IE11 compatibility.
                        </li>
                    </ul>
                    <h3>Fixed</h3>
                    <ul>
                        <li>Fixed a bug where Salesforce API connections were not
                            holding for more than a day or so.
                        </li>
                        <li>Fixed a bug where the Freeform 1.x to 2.x (Craft 2.x to 3.x)
                            migration path could error in some cases.
                        </li>
                        <li>Fixed a bug where deleting forms and fields were not
                            returning proper AJAX return statuses.
                        </li>
                        <li>Fixed a bug where the Constant Contact integration was not
                            correctly working.
                        </li>
                        <li>Fixed a bug where the Dynamic Recipients field as Checkbox
                            OR Radio would default to the first option being checked IF
                            none were selected and form reloaded after an error was
                            triggered.
                        </li>
                        <li>Fixed a bug where required asterisks were out of position in
                            CP submission view.
                        </li>
                        <li>Fixed a bug where permissions for Manage Export Profiles was
                            not working correctly.
                        </li>
                    </ul>
                </changelog-release>

                <changelog-release version="2.2.2" date="2018-08-02">
                    <h3>Changed</h3>
                    <ul>
                        <li>Updated Element Connections feature to allow mapping
                            Freeform File Upload fields to the User Photo field.
                        </li>
                        <li>Updated SharpSpring integration to work with all custom
                            field types.
                        </li>
                    </ul>
                    <h3>Fixed</h3>
                    <ul>
                        <li>Fixed a bug where the Element Connections feature would
                            display an error for customers using Solo edition.
                        </li>
                    </ul>
                </changelog-release>

                <changelog-release version="2.2.1" date="2018-07-19">
                    <h3>Changed</h3>
                    <ul>
                        <li>Updated HubSpot integration to load custom fields from
                            Contacts, Companies and Deals endpoints now, not just Deals.
                        </li>
                        <li>Updated Composer to display an error if any fields are using
                            the same handle.
                        </li>
                    </ul>
                    <h3>Fixed</h3>
                    <ul>
                        <li>Fixed a bug where some users were getting a 'fieldlayout'
                            table doesn't exist error in Composer.
                        </li>
                        <li>Fixed a bug where JS validation for the Phone fieldtype was
                            not working.
                        </li>
                        <li>Fixed a bug where admin notification recipients were not
                            being collected when using Windows newlines.
                        </li>
                    </ul>
                </changelog-release>
            </template>
        </div>
    </div>
</template>

<script>
    import ChangelogRelease from './ChangelogRelease'

    export default {

        data() {
            return {
                showMore: false,
            }
        },

        components: {
            ChangelogRelease
        }

    }
</script>


<style lang="scss">
    .plugin-changelog {
        .changelog-release:last-child {
            @apply .border-b-0;
        }
    }
</style>
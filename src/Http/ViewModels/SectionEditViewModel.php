<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Cp\SelectOptions;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\Data\ElementSiteSettings;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Enums\PropagationMethod;
use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Ui\Controls\Choice;
use CraftCms\Cms\Ui\Controls\EntryTypeSelect;
use CraftCms\Cms\Ui\Controls\Handle;
use CraftCms\Cms\Ui\Controls\Lightswitch;
use CraftCms\Cms\Ui\Controls\Number;
use CraftCms\Cms\Ui\Controls\Table;
use CraftCms\Cms\Ui\Controls\Text;
use CraftCms\Cms\Ui\Enums\ControlMode;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiPayload;
use CraftCms\Cms\Ui\UiResolver;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\Nodes\Group;
use CraftCms\Cms\Ui\Nodes\HiddenField;
use CraftCms\Cms\Ui\Nodes\Separator;
use CraftCms\Cms\Http\Controllers\Settings\SectionsController;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Section\Enums\DefaultPlacement;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Workflow\Models\Workflow;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class SectionEditViewModel extends ViewModel
{
    /** @param array<string, mixed>|null $values */
    public function __construct(
        private readonly Section $section,
        private readonly Sites $sites,

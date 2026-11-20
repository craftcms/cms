type ImportPlan = CraftCms.Cms.Import.Data.ImportPlanIndexData;

/** Something to do to every selected plan. */
export interface PlanBulkAction {
  key: string;
  label: string;
  destructive?: boolean;
  /** Which plans it can be done to; it's offered while any are selected, and given only them. */
  appliesTo?: (plan: ImportPlan) => boolean;
  perform: (plans: ImportPlan[]) => void;
}

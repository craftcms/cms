type ImportPlan = CraftCms.Cms.Import.Data.ImportPlanIndexData;

/** Something to do to every selected plan. */
export interface PlanBulkAction {
  key: string;
  label: string;
  destructive?: boolean;
  perform: (plans: ImportPlan[]) => void;
}

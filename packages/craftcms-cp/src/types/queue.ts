/** Job status constants matching server-side values */
export const JobStatus = {
  Pending: 1,
  Reserved: 2,
  Done: 3,
  Failed: 4,
  Delayed: 5,
  Cancelled: 6,
} as const;

export type JobStatusKey = (typeof JobStatus)[keyof typeof JobStatus];

/** Individual job information from server */
export interface JobInfo {
  uid: string;
  id: number;
  description: string;
  dateCreated: string;
  progress: number;
  progressLabel: string | null;
  status: {
    label: JobStatusKey;
    value: number;
  };
  delay: number;
  error?: string;
  label: string;
}

/** Response from queue/get-job-info endpoint */
export interface QueueJobData {
  total: number;
  jobs: JobInfo[];
}

/** Options for initializing QueueService */
export interface QueueServiceOptions {
  /** Whether queue tracking is enabled */
  enabled?: boolean;
  /** Application ID for cross-tab broadcasting */
  appId?: string;
  /** Whether current user can access queue manager */
  canAccessQueueManager?: boolean;
}

/** Detail for job-update event */
export interface JobUpdateDetail {
  totalJobs: number;
  jobInfo: JobInfo[];
  displayedJob: JobInfo | null;
}

/** Detail for job-failed event */
export interface JobFailedDetail {
  job: JobInfo;
}

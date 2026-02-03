<QueueManager
    view="index"
    :initialData='@json($initialData)'
    :totalJobs='@json($totalJobs)'
    :hasReservedJobs='@json($hasReservedJobs)'
    :hasWaitingJobs='@json($hasWaitingJobs)'
/>

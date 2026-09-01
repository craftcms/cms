import {
  afterEach,
  beforeEach,
  describe,
  expect,
  test,
  vi,
} from 'vite-plus/test';
import {QueueService} from './queue';
import {ConfigService} from '@craftcms/ui/services/Config';
import type {JobInfo, JobStatusKey} from './types';
import {JobStatus} from './types';

// Mock axios
vi.mock('axios', () => ({
  default: {
    post: vi.fn().mockResolvedValue({}),
    get: vi.fn().mockResolvedValue({data: {jobs: []}}),
  },
}));

// Mock ConfigService
vi.mock('@craftcms/ui/services/Config', () => ({
  ConfigService: {
    getInstance: vi.fn(() => ({
      getActionUrl: vi.fn(
        (path: string) => `https://example.com/actions/${path}`
      ),
    })),
  },
}));

describe('QueueService', () => {
  beforeEach(() => {
    QueueService.resetInstance();
    ConfigService.getInstance();
    vi.clearAllMocks();
  });

  afterEach(() => {
    QueueService.resetInstance();
  });

  describe('runQueue', () => {
    test('makes HTTP request and starts tracking when runAutomatically is true', async () => {
      const axios = await import('axios');
      const postSpy = vi.spyOn(axios.default, 'post');
      const queue = QueueService.getInstance();
      queue.initialize({runAutomatically: true});

      const startTrackingSpy = vi.spyOn(queue, 'startTracking');
      await queue.runQueue();

      expect(postSpy).toHaveBeenCalledWith(
        'https://example.com/actions/queue/run'
      );
      expect(startTrackingSpy).toHaveBeenCalledWith(false, true);
    });

    test('skips HTTP request but still tracks when runAutomatically is false', async () => {
      const axios = await import('axios');
      const postSpy = vi.spyOn(axios.default, 'post');
      const queue = QueueService.getInstance();
      queue.initialize({runAutomatically: false});

      const startTrackingSpy = vi.spyOn(queue, 'startTracking');
      await queue.runQueue();

      expect(postSpy).not.toHaveBeenCalled();
      expect(startTrackingSpy).toHaveBeenCalledWith(false, true);
    });
  });

  describe('setJobData', () => {
    test('selects reserved job as displayed job over pending', () => {
      const queue = QueueService.getInstance();
      queue.initialize();

      queue.setJobData([
        createMockJob({id: 1, status: JobStatus.Pending}),
        createMockJob({id: 2, status: JobStatus.Reserved}),
      ]);

      expect(queue.displayedJob?.id).toBe(2);
    });

    test('selects failed job as displayed job over pending', () => {
      const queue = QueueService.getInstance();
      queue.initialize();

      queue.setJobData([
        createMockJob({id: 1, status: JobStatus.Pending}),
        createMockJob({id: 2, status: JobStatus.Failed}),
      ]);

      expect(queue.displayedJob?.id).toBe(2);
    });

    test('skips delayed pending jobs when selecting displayed job', () => {
      const queue = QueueService.getInstance();
      queue.initialize();

      queue.setJobData([
        createMockJob({id: 1, status: JobStatus.Pending, delay: 60}),
        createMockJob({id: 2, status: JobStatus.Pending, delay: 0}),
      ]);

      expect(queue.displayedJob?.id).toBe(2);
    });

    test('emits job-complete event when jobs become empty', () => {
      const queue = QueueService.getInstance();
      queue.initialize();
      queue.setJobData([createMockJob({id: 1, status: JobStatus.Pending})]);

      const handler = vi.fn();
      queue.addEventListener('job-complete', handler);
      queue.setJobData([]);

      expect(handler).toHaveBeenCalled();
    });

    test('emits job-failed event when displayed job is failed', () => {
      const queue = QueueService.getInstance();
      queue.initialize();

      const handler = vi.fn();
      queue.addEventListener('job-failed', handler);
      queue.setJobData([createMockJob({id: 1, status: JobStatus.Failed})]);

      expect(handler).toHaveBeenCalled();
    });
  });
});

function createMockJob(overrides: {
  id?: number;
  status: JobStatusKey;
  delay?: number;
}): JobInfo {
  const id = overrides.id ?? 1;
  return {
    uid: `uid-${id}`,
    id: id,
    description: 'Test job',
    dateCreated: '2024-01-01',
    progress: 0,
    progressLabel: null,
    status: {
      label: overrides.status,
      value: overrides.status,
    },
    delay: overrides.delay ?? 0,
    label: 'Test job',
  };
}

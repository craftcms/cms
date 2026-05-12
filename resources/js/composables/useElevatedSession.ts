import {ref} from 'vue';
import {usePage} from '@inertiajs/vue3';

type PendingAction = () => void;

const modalOpen = ref(false);
let pendingAction: PendingAction | null = null;
let localExpiresAt: number | false | null = null;

function isElevated(): boolean {
  if (localExpiresAt === false) {
    return true;
  }

  const expiresAt = localExpiresAt ?? getSharedExpiresAt();

  if (expiresAt === false) {
    return true;
  }

  return expiresAt > 0 && Math.floor(Date.now() / 1000) < expiresAt;
}

function getSharedExpiresAt(): number | false {
  const page = usePage<{craft: {elevatedSessionExpiresAt: number | false}}>();
  return page.props.craft?.elevatedSessionExpiresAt ?? 0;
}

function requireElevatedSession(action: PendingAction): void {
  if (isElevated()) {
    action();
    return;
  }

  pendingAction = action;
  modalOpen.value = true;
}

function onConfirmed(expiresAt: number | false): void {
  localExpiresAt = expiresAt;
  modalOpen.value = false;

  const action = pendingAction;
  pendingAction = null;
  action?.();
}

function onCancel(): void {
  pendingAction = null;
  modalOpen.value = false;
}

export function useElevatedSession() {
  return {
    modalOpen,
    requireElevatedSession,
    onConfirmed,
    onCancel,
  };
}

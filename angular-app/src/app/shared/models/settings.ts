export interface AppSettings {
  notify_showSignals: boolean;
  notify_showInfos: boolean;
  notify_showSuccesses: boolean;
  notify_autoHideSuccesses: boolean;
  notify_showErrors: boolean;
  notify_showWarnings: boolean;
  notify_showInvariants: boolean;
  autoSave: boolean;
}

export const defaultSettings: AppSettings = {
  notify_showSignals: true,
  notify_showInfos: true,
  notify_showSuccesses: true,
  notify_autoHideSuccesses: true,
  notify_showErrors: true,
  notify_showWarnings: true,
  notify_showInvariants: true,
  autoSave: true,
};

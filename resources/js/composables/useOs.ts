export const isMac = navigator.platform.toUpperCase().includes('MAC');

export function formatHotkey(hotkey: string): string {
    if (isMac) {
        return hotkey.replace('CmdOrCtrl+', '⌘').replace('Shift+', '⇧');
    }

    return hotkey.replace('CmdOrCtrl+', 'Ctrl+');
}

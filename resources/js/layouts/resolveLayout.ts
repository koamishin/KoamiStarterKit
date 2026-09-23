import AppLayout from './AppLayout.vue';

/**
 * Global Inertia layout resolver.
 *
 * Pages that self-wrap (Welcome, auth/*, Dashboard, settings/*) return null
 * so they are not double-wrapped by the global layout. Module pages that do
 * not self-wrap still get AppLayout.
 */
export function resolveLayout(name: string): typeof AppLayout | null {
    switch (true) {
        case name === 'Welcome':
        case name === 'Dashboard':
        case name.startsWith('auth/'):
        case name.startsWith('settings/'):
            return null;
        default:
            return AppLayout;
    }
}

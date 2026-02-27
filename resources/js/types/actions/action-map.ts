import {
    Ban,
    CheckCircle,
    Download,
    Ellipsis,
    Eye,
    FileDown,
    Key,
    Minus,
    Pen,
    Plus,
    Trash2,
    Upload,
} from 'lucide-vue-next';
import type { Action } from './action';

export const Actions = {
    create: {
        id: 'create',
        kind: 'open-flow',
        icon: Plus,
        variant: 'default',
    },
    edit: {
        id: 'edit',
        kind: 'open-flow',
        icon: Pen,
        variant: 'outline',
    },
    view: {
        id: 'view',
        kind: 'open-flow',
        icon: Eye,
        variant: 'outline',
    },
    more: {
        id: 'more',
        kind: 'open-flow',
        icon: Ellipsis,
        variant: 'ghost',
        defaultSize: 'icon-sm',
    },
    save: {
        id: 'save',
        kind: 'commit',
        icon: CheckCircle,
        variant: 'default',
    },
    delete: {
        id: 'delete',
        kind: 'removal',
        icon: Trash2,
        variant: 'destructive',
        destructive: true,
    },
    remove: {
        id: 'remove',
        kind: 'removal',
        icon: Minus,
        variant: 'outline',
    },
    disable: {
        id: 'disable',
        kind: 'state',
        icon: Ban,
        variant: 'destructive',
        destructive: true,
    },
    enable: {
        id: 'enable',
        kind: 'state',
        icon: CheckCircle,
        variant: 'default',
    },
    permissions: {
        id: 'permissions',
        kind: 'open-flow',
        icon: Key,
        variant: 'outline',
    },
    import: {
        id: 'import',
        kind: 'data',
        icon: Upload,
        variant: 'outline',
    },
    export: {
        id: 'export',
        kind: 'data',
        icon: FileDown,
        variant: 'outline',
    },
    download: {
        id: 'download',
        kind: 'data',
        icon: Download,
        variant: 'outline',
    },
} satisfies Record<string, Action>;

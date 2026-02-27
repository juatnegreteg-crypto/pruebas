import type { LucideIcon } from 'lucide-vue-next';
import type { ButtonVariants } from '@/components/ui/button';

export type ActionId =
    | 'create'
    | 'edit'
    | 'view'
    | 'more'
    | 'save'
    | 'delete'
    | 'remove'
    | 'disable'
    | 'enable'
    | 'import'
    | 'export'
    | 'download';

export type ActionKind = 'open-flow' | 'commit' | 'removal' | 'state' | 'data';

export type Action = {
    id: ActionId;
    kind: ActionKind;
    icon: LucideIcon;
    variant: ButtonVariants['variant'];
    destructive?: boolean;
    defaultSize?: ButtonVariants['size'];
};

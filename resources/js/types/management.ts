import type { Role } from './auth';

export type AvailablePermission = {
    value: string;
    label: string;
    /** Product area, used to group the list instead of one flat column. */
    area: string;
    /** True when the permission hands out control of the project itself. */
    administrative: boolean;
    /** Permissions that must be granted alongside this one. */
    requires: string[];
};

/** A role as listed on the permissions screen, with whether the viewer may edit it. */
export type EditableRole = Role & {
    /** A read-only role holds no permissions; its checkboxes are inert. */
    read_only: boolean;
    can_edit: boolean;
};

/** A role as offered on the accounts screen, with whether the viewer may hand it out. */
export type AssignableRole = Pick<Role, 'id' | 'name' | 'slug'> & {
    assignable: boolean;
};

/** A user account as listed on the accounts screen. */
export type Account = {
    id: number;
    name: string;
    email: string;
    role: Pick<Role, 'id' | 'name' | 'slug'> | null;
    can_change_role: boolean;
};

export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type AuthorizationPayload = {
    user: {
        id: number;
        name: string;
        email: string;
        isActive: boolean;
    };
    profile: {
        id: number;
        name: string;
        slug: string;
    } | null;
    permissions: string[];
    skills: Array<{
        id: number;
        name: string;
        slug: string;
    }>;
    version: number;
    generatedAt: string;
};

export type Auth = {
    user: User;
    authorization?: AuthorizationPayload | null;
};

export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};

export type Contents = {
    id: number;
    type: string;
    title: string;
    body: string;
    file_url: string;
    thumbnail_url: string;
    published_at: string;
    updated_at?: string;
    images: ContentsImage[] | null;
    file_type: string | null;
    department?: Department | null;
    comments?: Comment[];
    comments_count?: number;
    tags?: Tag[];
};

export type Department = {
    id: number;
    name: string;
    icon_image: string;
};

export type ContentsImage = {
    id: number;
    page: number;
    file_url: string;
};

export type Comment = {
    id: number;
    content_id: number;
    user_id: number | null;
    guest_name: string | null;
    ip_address: string | null;
    body: string;
    created_at: string;
    updated_at: string;
    user?: {
        id: number;
        name: string;
        email: string;
    };
    display_name: string;
    ip_last_digits: string;
};

export type ContentsPlatformComment = {
    id: number;
    content_id: number;
    comment_id: string;
    source: string;
    author: string;
    content: string;
    likes: number;
    dislikes: number;
    created_date: string | null;
    is_best: boolean;
    is_mobile: boolean;
    reply_count: number;
    created_at: string;
    updated_at: string;
};

export type Tag = {
    id: number;
    name: string;
    slug: string;
    usage_count: number;
};

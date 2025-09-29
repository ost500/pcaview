export type Contents = {
    id: number;
    title: string;
    body: string;
    file_url: string;
    thumbnail_url: string;
    published_at: string;
    images: ContentsImage[] | null;
    file_type: string | null;
};

export type ContentsImage = {
    id: number;
    page: number;
    file_url: string;
};

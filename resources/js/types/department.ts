export type Department = {
    id: number;
    church_id: number | null;
    name: string;
    icon_image: string;
    church?: {
        id: number;
        name: string;
        display_name?: string | null;
        icon_url: string;
        logo_url: string;
        worship_time_image: string;
        address: string;
        address_url: string;
    };
};

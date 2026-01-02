import type { Department } from './department';

export type Church = {
    id: number;
    name: string;
    slug: string;
    icon_url: string;
    logo_url: string;
    worship_time_image: string;
    address: string;
    address_url: string;
    departments?: Department[];
};

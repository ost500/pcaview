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
    primary_department_id: number | null;
    primary_department?: Department;
    departments?: Department[];
};

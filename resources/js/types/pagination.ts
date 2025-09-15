export type Pagination<T> = {
    data: T[]; // 게시물 배열
    current_page: number; // 현재 페이지 번호
    last_page: number; // 마지막 페이지 번호
    next_page_url: string | null; // 다음 페이지 URL
    prev_page_url: string | null; // 이전 페이지 URL
};

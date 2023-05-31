interface IPaginationResponse<T> {
  data: T[];
  count: number;
  page: number;
  pageCount: number;
  total: number;
}

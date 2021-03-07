export interface NavItem {
  label: string;
  seqNr: number;
  children: Array<NavItem>;
  url?: string;
  ifc?: string;
}

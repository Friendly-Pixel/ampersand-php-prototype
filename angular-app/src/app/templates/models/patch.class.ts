export class Patch {
  constructor(
    public op: string,
    public path: string,
    public value: any | null
  ) {}
}

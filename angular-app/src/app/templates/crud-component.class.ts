export abstract class CRUDComponent {
  public crud: string = "cRud";
  isCrudC(): boolean {
    return this.crud.includes("C");
  }
  isCrudR(): boolean {
    return this.crud.includes("R");
  }
  isCrudU(): boolean {
    return this.crud.includes("U");
  }
  isCrudD(): boolean {
    return this.crud.includes("D");
  }
}

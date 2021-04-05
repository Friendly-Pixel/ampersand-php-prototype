import { Component, Input, OnInit } from "@angular/core";

@Component({
  selector: "app-leaf-alphanumeric",
  templateUrl: "./leaf-alphanumeric.component.html",
  styleUrls: ["./leaf-alphanumeric.component.css"],
})
export class LeafAlphanumericComponent implements OnInit {
  public crudC = false;
  public crudR = true;
  public crudU = true;
  public crudD = false;

  public isUni = true;
  public isTot = false;

  @Input()
  public resource: any;

  @Input()
  public field: string;

  public newValue: string;

  constructor() {}

  ngOnInit(): void {}

  getValues(): Array<string> {
    return this.isUni ? [this.resource[this.field]] : this.resource[this.field];
  }

  // TODO: implement
  save() {}
  addItem() {
    this.newValue = "";
  }
  removeItem(index) {}
}

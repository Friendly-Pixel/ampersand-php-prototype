import { Component, Input, OnInit } from "@angular/core";
import { CRUDComponent } from "../crud-component.class";

@Component({
  selector: "app-leaf-alphanumeric",
  templateUrl: "./leaf-alphanumeric.component.html",
  styleUrls: ["./leaf-alphanumeric.component.css"],
})
export class LeafAlphanumericComponent extends CRUDComponent implements OnInit {
  @Input() public crud: string;
  @Input() public isUni = true;
  @Input() public isTot = false;
  @Input() public resource: any;
  @Input() public field: string;

  public newValue: string;

  constructor() {
    super();
  }

  ngOnInit(): void {}

  getValues(): Array<string> {
    return Array.isArray(this.resource[this.field])
      ? this.resource[this.field]
      : [this.resource[this.field]];
  }

  // TODO: implement
  save() {
    console.log("Save: ", this.resource);
  }
  addItem() {
    this.newValue = "";
  }
  removeItem(index) {}
}

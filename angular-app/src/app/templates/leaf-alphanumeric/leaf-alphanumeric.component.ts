import { Component, Input, OnInit } from "@angular/core";

@Component({
  selector: "app-leaf-alphanumeric",
  templateUrl: "./leaf-alphanumeric.component.html",
  styleUrls: ["./leaf-alphanumeric.component.css"],
})
export class LeafAlphanumericComponent implements OnInit {
  public crudC = false;
  public crudR = true;
  public crudU = false;
  public crudD = false;

  public isUni = true;
  public isTot = false;

  @Input()
  public list: Array<string> = []; // used when this component has non-uni expression

  @Input()
  public value: string; // used when this component has uni expression

  public newValue: string;

  constructor() {}

  ngOnInit(): void {}

  // TODO: implement
  save() {}
  addItem() {
    this.newValue = "";
  }
  removeItem(index) {}
}

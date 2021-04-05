import { Component, Input, OnInit } from "@angular/core";

@Component({
  selector: "app-box-form-row",
  templateUrl: "./box-form-row.component.html",
  styleUrls: ["./box-form-row.component.css"],
})
export class BoxFormRowComponent implements OnInit {
  @Input()
  public label: string;

  @Input()
  public hideLabel = false;

  constructor() {}

  ngOnInit(): void {}
}

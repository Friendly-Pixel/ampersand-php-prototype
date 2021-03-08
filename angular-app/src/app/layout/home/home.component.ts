import { Component, OnInit } from "@angular/core";

@Component({
  selector: "app-home",
  templateUrl: "./home.component.html",
  styleUrls: ["./home.component.css"],
})
export class HomeComponent implements OnInit {
  numberOfCols: number;
  constructor() {}

  ngOnInit(): void {
    this.numberOfCols = this.getColsFor(window.innerWidth);
  }

  onResize(event) {
    this.numberOfCols = this.getColsFor(event.target.innerWidth);
  }

  protected getColsFor(width: number): number {
    if (width < 600) {
      return 1;
    }
    if (width < 1024) {
      return 2;
    }
    if (width < 1440) {
      return 3;
    }

    return 4;
  }
}

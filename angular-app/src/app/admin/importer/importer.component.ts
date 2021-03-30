import { Component, OnInit } from "@angular/core";
import { FileUploader } from "ng2-file-upload";

@Component({
  selector: "app-importer",
  templateUrl: "./importer.component.html",
  styleUrls: ["./importer.component.css"],
})
export class ImporterComponent implements OnInit {
  uploader: FileUploader;
  hasBaseDropZoneOver: boolean;
  hasAnotherDropZoneOver: boolean;
  response: string;

  constructor() {
    this.uploader = new FileUploader({
      url: "api/v1/admin/import",
    });

    this.hasBaseDropZoneOver = false;
    this.response = "";
    this.uploader.response.subscribe((res) => (this.response = res));
  }

  ngOnInit(): void {}

  public fileOverBase(e: any): void {
    this.hasBaseDropZoneOver = e;
  }
}

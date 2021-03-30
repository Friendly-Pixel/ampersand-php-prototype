import { Component, OnInit } from "@angular/core";
import { FileUploader } from "ng2-file-upload";
import { NavbarService } from "src/app/navbar/navbar.service";
import { NotificationCenterService } from "src/app/notification-center/notification-center.service";

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

  constructor(
    protected navBar: NavbarService,
    protected notifySvc: NotificationCenterService
  ) {
    this.uploader = new FileUploader({
      url: "api/v1/admin/import",
    });

    this.uploader.onSuccessItem = (fileItem, res, status, headers) => {
      let response = JSON.parse(res);
      this.notifySvc.updateNotifications(response.notifications);
      if (response.sessionRefreshAdvice) this.navBar.refreshNavBar();
    };

    this.uploader.onErrorItem = (item, res, status, headers) => {
      let response = JSON.parse(res);
      let message: string;
      let details: string;
      if (typeof response === "object") {
        if (response.notifications !== undefined) {
          this.notifySvc.updateNotifications(response.notifications);
        }
        message = response.msg || "Error while importing";
        this.notifySvc.error(message);
        // .addError(message, status, true, response.html);
      } else {
        message = status + " Error while importing";
        details = response; // html content is excepted
        this.notifySvc.error(message);
        // .addError(message, status, true, details);
      }
    };

    this.hasBaseDropZoneOver = false;
    this.response = "";
    this.uploader.response.subscribe((res) => (this.response = res));
  }

  ngOnInit(): void {}

  public fileOverBase(e: any): void {
    this.hasBaseDropZoneOver = e;
  }
}

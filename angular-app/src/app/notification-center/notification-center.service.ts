import { Injectable } from "@angular/core";
import {
  MatSnackBar,
  MatSnackBarHorizontalPosition,
  MatSnackBarVerticalPosition,
} from "@angular/material/snack-bar";
import { ApiService } from "../api.service";

@Injectable({
  providedIn: "root",
})
export class NotificationCenterService {
  horizontalPosition: MatSnackBarHorizontalPosition = "center";
  verticalPosition: MatSnackBarVerticalPosition = "bottom";
  public nrOfNotifications: number = 10;

  constructor(protected snackBar: MatSnackBar, protected api: ApiService) {}

  public updateNotifications(data) {}

  public clearNotifications() {}

  public checkAllRules() {
    this.api.get("admin/ruleengine/evaluate/all").then(
      (data) => {
        this.success("All rules evaluated");
        this.updateNotifications(data);
      },
      (err) => {
        this.error("Something went wrong while evaluating all rules");
      }
    );
  }

  notify(message: string, actionMsg: string = "Dismiss") {
    return this.snackBar.open(message, actionMsg, {
      duration: 3000,
      horizontalPosition: this.horizontalPosition,
      verticalPosition: this.verticalPosition,
    });
  }

  success(message: string, actionMsg: string = "Dismiss") {
    return this.snackBar.open(message, actionMsg, {
      duration: 3000,
      horizontalPosition: this.horizontalPosition,
      verticalPosition: this.verticalPosition,
    });
  }

  error(message: string, actionMsg: string = "Dismiss") {
    return this.snackBar.open(message, actionMsg, {
      duration: 0, // don't automatically dismiss
      horizontalPosition: this.horizontalPosition,
      verticalPosition: this.verticalPosition,
    });
  }
}

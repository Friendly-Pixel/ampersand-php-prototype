import { Injectable } from "@angular/core";
import {
  MatSnackBar,
  MatSnackBarHorizontalPosition,
  MatSnackBarVerticalPosition,
} from "@angular/material/snack-bar";

@Injectable({
  providedIn: "root",
})
export class NotificationCenterService {
  horizontalPosition: MatSnackBarHorizontalPosition = "center";
  verticalPosition: MatSnackBarVerticalPosition = "bottom";

  constructor(protected snackBar: MatSnackBar) {}

  public updateNotifications(data) {}

  public clearNotifications() {}

  public checkAllRules() {}

  notify(message: string, actionMsg: string = "Dismiss") {
    return this.snackBar.open(message, actionMsg, {
      duration: 3000,
      horizontalPosition: this.horizontalPosition,
      verticalPosition: this.verticalPosition,
    });
  }
}

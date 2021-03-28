import { NgModule } from "@angular/core";
import { Routes, RouterModule } from "@angular/router";
import { InstallerComponent } from "./admin/installer/installer.component";
import { HomeComponent } from "./layout/home/home.component";
import { NotificationCenterComponent } from "./notification-center/notification-center.component";

const routes: Routes = [
  { path: "home", component: HomeComponent },
  { path: "prototype/welcome", component: HomeComponent },
  { path: "admin/installer", component: InstallerComponent },
  { path: "notifications", component: NotificationCenterComponent },
];

@NgModule({
  imports: [RouterModule.forRoot(routes, { relativeLinkResolution: "legacy" })],
  exports: [RouterModule],
})
export class AppRoutingModule {}

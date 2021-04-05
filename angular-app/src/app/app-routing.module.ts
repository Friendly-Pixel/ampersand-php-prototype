import { NgModule } from "@angular/core";
import { Routes, RouterModule } from "@angular/router";
import { ImporterComponent } from "./admin/importer/importer.component";
import { InstallerComponent } from "./admin/installer/installer.component";
import { IfcNewEditProjectComponent } from "./generated/ifc-new-edit-project/ifc-new-edit-project.component";
import { HomeComponent } from "./layout/home/home.component";
import { NotificationCenterComponent } from "./notification-center/notification-center.component";

const routes: Routes = [
  { path: "home", component: HomeComponent },
  { path: "prototype/welcome", component: HomeComponent },
  { path: "admin/installer", component: InstallerComponent },
  { path: "admin/importer", component: ImporterComponent },
  { path: "notifications", component: NotificationCenterComponent },
  {
    path: "new-edit-project/:projectId",
    component: IfcNewEditProjectComponent,
  },
];

@NgModule({
  imports: [RouterModule.forRoot(routes, { relativeLinkResolution: "legacy" })],
  exports: [RouterModule],
})
export class AppRoutingModule {}

import { NgModule } from "@angular/core";
import { Routes, RouterModule } from "@angular/router";
import { HomeComponent } from "./layout/home/home.component";

const routes: Routes = [
  { path: "home", component: HomeComponent },
  { path: "prototype/welcome", component: HomeComponent },
  { path: "admin/installer", component: HomeComponent },
];

@NgModule({
  imports: [RouterModule.forRoot(routes, { relativeLinkResolution: "legacy" })],
  exports: [RouterModule],
})
export class AppRoutingModule {}

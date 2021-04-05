import { BrowserModule } from "@angular/platform-browser";
import { NgModule, CUSTOM_ELEMENTS_SCHEMA } from "@angular/core";
import { FormsModule } from "@angular/forms";
import { HttpClientModule } from "@angular/common/http";

import { AppRoutingModule } from "./app-routing.module";
import { AppComponent } from "./app.component";
import { NavbarComponent } from "./navbar/navbar.component";
import { MenuItemsComponent } from "./navbar/menu-items/menu-items.component";
import { NavbarItemComponent } from "./navbar/navbar-item/navbar-item.component";
import { NotificationCenterComponent } from "./notification-center/notification-center.component";

import { NgxWebstorageModule } from "ngx-webstorage";
import { FileUploadModule } from "ng2-file-upload";
import { NoopAnimationsModule } from "@angular/platform-browser/animations";

import { FlexLayoutModule } from "@angular/flex-layout";
import { MatSlideToggleModule } from "@angular/material/slide-toggle";
import { MatToolbarModule } from "@angular/material/toolbar";
import { MatIconModule } from "@angular/material/icon";
import { MatButtonModule } from "@angular/material/button";
import { MatMenuModule } from "@angular/material/menu";
import { MatDividerModule } from "@angular/material/divider";
import { MatSidenavModule } from "@angular/material/sidenav";
import { MatListModule } from "@angular/material/list";
import { MatCardModule } from "@angular/material/card";
import { MatGridListModule } from "@angular/material/grid-list";
import { MatSnackBar } from "@angular/material/snack-bar";
import { MatBadgeModule } from "@angular/material/badge";
import { NgxSpinnerModule } from "ngx-spinner";

import { HomeComponent } from "./layout/home/home.component";
import { InstallerComponent } from "./admin/installer/installer.component";
import { ImporterComponent } from "./admin/importer/importer.component";
import { NgbModule } from "@ng-bootstrap/ng-bootstrap";
import { TemplatesModule } from "./templates/templates.module";
import { GeneratedModule } from "./generated/generated.module";

@NgModule({
  declarations: [
    AppComponent,
    NavbarComponent,
    NotificationCenterComponent,
    MenuItemsComponent,
    NavbarItemComponent,
    HomeComponent,
    InstallerComponent,
    ImporterComponent,
  ],
  imports: [
    BrowserModule,
    FormsModule,
    AppRoutingModule,
    NgxWebstorageModule.forRoot({
      prefix: "app",
      separator: ".",
      caseSensitive: true,
    }),
    HttpClientModule, // import HttpClientModule after BrowserModule.
    NoopAnimationsModule,
    FlexLayoutModule,
    MatSlideToggleModule,
    MatToolbarModule,
    MatIconModule,
    MatButtonModule,
    MatMenuModule,
    MatSidenavModule,
    MatListModule,
    MatDividerModule,
    MatCardModule,
    MatGridListModule,
    MatBadgeModule,
    NgxSpinnerModule,
    FileUploadModule,
    NgbModule,
    TemplatesModule,
    GeneratedModule,
  ],
  schemas: [CUSTOM_ELEMENTS_SCHEMA],
  providers: [MatSnackBar],
  bootstrap: [AppComponent],
})
export class AppModule {}

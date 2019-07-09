import { BrowserModule } from '@angular/platform-browser';
import { NgModule } from '@angular/core';
import { HttpClientModule } from '@angular/common/http';

import { AppRoutingModule } from './app-routing.module';
import { AppComponent } from './app.component';
import { NavbarComponent } from './navbar/navbar.component';
import { NotificationCenterComponent } from './notification-center/notification-center.component';

import { AngularWebStorageModule } from 'angular-web-storage';

@NgModule({
  declarations: [
    AppComponent,
    NavbarComponent,
    NotificationCenterComponent
  ],
  imports: [
    BrowserModule,
    AppRoutingModule,
    AngularWebStorageModule,
    HttpClientModule, // import HttpClientModule after BrowserModule.
  ],
  providers: [],
  bootstrap: [AppComponent]
})
export class AppModule { }

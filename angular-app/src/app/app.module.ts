import { BrowserModule } from '@angular/platform-browser';
import { NgModule } from '@angular/core';
import { HttpClientModule } from '@angular/common/http';

import { AppRoutingModule } from './app-routing.module';
import { AppComponent } from './app.component';
import { NavbarComponent } from './navbar/navbar.component';
import { NotificationCenterComponent } from './notification-center/notification-center.component';

import { RestangularModule, Restangular } from 'ngx-restangular';
// Function for setting the default restangular configuration
export function RestangularConfigFactory (RestangularProvider) {
  RestangularProvider.setBaseUrl('api/v1');
  RestangularProvider.setDefaultHeaders({'Content-Type': 'application/json'});
  RestangularProvider.setPlainByDefault(true);
}

@NgModule({
  declarations: [
    AppComponent,
    NavbarComponent,
    NotificationCenterComponent
  ],
  imports: [
    BrowserModule,
    AppRoutingModule,
    HttpClientModule,
    RestangularModule.forRoot(RestangularConfigFactory)
  ],
  providers: [],
  bootstrap: [AppComponent]
})
export class AppModule { }

import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { InstallerComponent } from './installer/installer.component';
import { RouterModule, Routes } from '@angular/router';
import { AppLayoutComponent } from '../layout/app.layout.component';
import { CardModule } from 'primeng/card';
import { ButtonModule } from 'primeng/button';
import { MenuItem } from 'primeng/api';
import { UtilsComponent } from './utils/utils.component';
import { PopulationComponent } from './population/population.component';

const routes: Routes = [
  {
    path: 'admin',
    component: AppLayoutComponent,
    children: [
      { path: 'installer', component: InstallerComponent },
      {
        path: 'utils',
        component: UtilsComponent,
      },
      {
        path: 'population',
        component: PopulationComponent,
      },
    ],
  },
];

export const menuItems: MenuItem[] = [
  {
    label: 'Admin',
    items: [
      { label: 'Installer', icon: 'pi pi-fw pi-replay', routerLink: ['/admin/installer'] },
      { label: 'Utils', icon: 'pi pi-fw pi-cog', routerLink: ['/admin/utils'] },
      { label: 'Population', icon: 'pi pi-fw pi-users', routerLink: ['/admin/population'] },
    ],
  },
];

@NgModule({
  declarations: [InstallerComponent, UtilsComponent, PopulationComponent],
  imports: [CommonModule, RouterModule.forChild(routes), CardModule, ButtonModule],
})
export class AdminModule {}

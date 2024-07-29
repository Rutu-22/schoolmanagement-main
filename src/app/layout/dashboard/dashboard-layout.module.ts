import { RouterModule } from '@angular/router';
import { FooterComponent } from './footer/footer.component';
import { LeftNavBarComponent } from './navigation/left-nav-bar/left-nav-bar.component';
import { TopBarComponent } from './navigation/top-bar/top-bar.component';
import { DashboardLayoutComponent } from './dashboard-layout.component';
import { NgModule, APP_INITIALIZER } from '@angular/core';
import { BrowserModule } from '@angular/platform-browser';

import { NgbModule, NgbDropdownModule } from '@ng-bootstrap/ng-bootstrap';
import { FontAwesomeModule } from '@fortawesome/angular-fontawesome';

@NgModule({
  declarations: [
    DashboardLayoutComponent,
    TopBarComponent,
    LeftNavBarComponent,
    FooterComponent,
  ],
  imports: [
    BrowserModule,
    NgbModule,
    FontAwesomeModule,
    NgbDropdownModule,
    RouterModule,
  ],
  providers: [],
  bootstrap: [DashboardLayoutComponent],
})
export class DashboardLayoutModule {}

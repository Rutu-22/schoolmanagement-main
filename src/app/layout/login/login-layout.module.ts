import { UserLoginComponent } from '../../pages/login/containers/user-login/user-login.component';
import { LoginLayoutComponent } from './login-layout.component';
import { RouterModule } from '@angular/router';
import { NgModule } from '@angular/core';
import { BrowserModule } from '@angular/platform-browser';

import { NgbModule, NgbDropdownModule } from '@ng-bootstrap/ng-bootstrap';
import { FontAwesomeModule } from '@fortawesome/angular-fontawesome';
import { LoginFooterComponent } from './login-footer/login-footer.component';

@NgModule({
  declarations: [
    LoginLayoutComponent,
    LoginFooterComponent,
    UserLoginComponent
  ],
  imports: [
    BrowserModule,
    NgbModule,
    FontAwesomeModule,
    NgbDropdownModule, RouterModule
  ],
  providers: [],
  bootstrap: [LoginLayoutComponent]
})
export class LoginLayoutModule { }

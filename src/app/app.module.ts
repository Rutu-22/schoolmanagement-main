import { AppCommonModule } from './common/app.common.module';
import { LoaderInterceptor } from './common/interceptors/loaderInterceptor';
import { LoginLayoutModule } from './layout/login/login-layout.module';
import { DashboardLayoutModule } from './layout/dashboard/dashboard-layout.module';
import { NO_ERRORS_SCHEMA, NgModule } from '@angular/core';
import { BrowserModule } from '@angular/platform-browser';
import { AppRoutingModule } from './app-routing.module';
import { AppComponent } from './app.component';
import { DashboardComponent } from './pages/dashboard/dashboard.component';
import { NgbModule, NgbDropdownModule } from '@ng-bootstrap/ng-bootstrap';
import { FontAwesomeModule } from '@fortawesome/angular-fontawesome';
import { HttpClientModule, HTTP_INTERCEPTORS, HttpClient } from '@angular/common/http';
import { CommonModule } from '@angular/common';
import { WorkStationMqttService } from './common/services/workstation.mqtt.service';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { ToastrModule } from 'ngx-toastr';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import { RegistrationComponent } from './registration/registration.component';
import { TranslateModule, TranslateLoader } from '@ngx-translate/core';
import { TranslateHttpLoader } from '@ngx-translate/http-loader';
import { LanguageService } from './language.service';
import { StudentDetailsComponent } from './student-details/student-details.component';
import { BonafideCertificateComponent } from './bonafide-certificate/bonafide-certificate.component';
import { TcCertificateComponent } from './tc-certificate/tc-certificate.component';
import { SchoolsComponent } from './pages/schools/schools.component';
import { CasteComponent } from './caste/caste.component';
import { DivisionComponent } from './division/division.component';
import { AddressComponent } from './address/address.component';

export function HttpLoaderFactory(http: HttpClient) {
  return new TranslateHttpLoader(http, './assets/i18n/', '.json');
}
@NgModule({
  declarations: [AppComponent, DashboardComponent, RegistrationComponent, StudentDetailsComponent, BonafideCertificateComponent,TcCertificateComponent, SchoolsComponent, CasteComponent, DivisionComponent, AddressComponent  ],
  imports: [
    BrowserModule,
    HttpClientModule,
    CommonModule,
    BrowserAnimationsModule,
    ToastrModule.forRoot(),
    AppCommonModule,
    AppRoutingModule,
    NgbModule,
    FormsModule,
    ReactiveFormsModule,
    DashboardLayoutModule,
    LoginLayoutModule,
    FontAwesomeModule,
    NgbDropdownModule,
    HttpClientModule,TranslateModule.forRoot({
      loader: {
        provide: TranslateLoader,
        useFactory: HttpLoaderFactory,
        deps: [HttpClient]
      }
    })

  ],
  schemas: [NO_ERRORS_SCHEMA],
  providers: [LanguageService,
    { provide: HTTP_INTERCEPTORS, useClass: LoaderInterceptor, multi: true },
    { provide: WorkStationMqttService, useClass: WorkStationMqttService }
  ],
  bootstrap: [AppComponent],
})
export class AppModule {}

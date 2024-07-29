import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import { BonafideCertificateComponent } from './bonafide-certificate/bonafide-certificate.component';
import { DashboardLayoutComponent } from './layout/dashboard/dashboard-layout.component';
import { LoginLayoutComponent } from './layout/login/login-layout.component';
import { DashboardComponent } from './pages/dashboard/dashboard.component';
import { UserLoginComponent } from './pages/login/containers/user-login/user-login.component';
import { RegistrationComponent } from './registration/registration.component';
import { StudentDetailsComponent } from './student-details/student-details.component';
import { TcCertificateComponent } from './tc-certificate/tc-certificate.component';
import { SchoolsComponent } from './pages/schools/schools.component';
import { CasteComponent } from './caste/caste.component';
import { DivisionComponent } from './division/division.component';
import { AddressComponent } from './address/address.component';

const routes: Routes = [
  {
    path: '',
    component: DashboardLayoutComponent,
    children: [
      {
        path: 'workstation-status',
        loadChildren: () => import('./pages/Workstation-Status/workstation-status.module').then((m) => m.WorkStationStatusModule),
      },
      { path: 'registration', 
      component: RegistrationComponent 
      },
      {
        path: 'student-details',
        component: StudentDetailsComponent
      },
      { path: 'student-list', 
      component: StudentDetailsComponent },

      {path:'bonafide-certificate',
      component: BonafideCertificateComponent},
 
    
      { path: 'tc', 
      component: TcCertificateComponent },
      {path: 'schools', 
      component: SchoolsComponent},
      {path: 'caste', 
      component: CasteComponent},
      {path: 'division', 
      component: DivisionComponent},
      {path: 'address', 
      component: AddressComponent}

    ],
  },
  {
    path: 'login',
    component: LoginLayoutComponent,
    children: [{ path: '', component: UserLoginComponent }],
  },
];

@NgModule({
  imports: [RouterModule.forRoot(routes)],
  exports: [RouterModule],
})
export class AppRoutingModule {}

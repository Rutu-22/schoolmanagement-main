import { LoaderInterceptor } from './interceptors/loaderInterceptor';
import { HTTP_INTERCEPTORS } from '@angular/common/http';
import { NgModule } from '@angular/core';
import { LoaderComponent } from './components/loader/loader.component';
import { CommonModule } from '@angular/common';

@NgModule({
  declarations: [LoaderComponent],
  imports: [
    CommonModule
  ],
  exports:[LoaderComponent],
  providers: [
    { provide: HTTP_INTERCEPTORS, useClass: LoaderInterceptor, multi: true },
  ],
  bootstrap: [],
})

export class AppCommonModule {}

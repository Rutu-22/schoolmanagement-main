import { LoaderService } from './common/services/loader.service';
import { Subject } from 'rxjs';
import { LoaderComponent } from './common/components/loader/loader.component';
import { TestBed } from '@angular/core/testing';
import { RouterTestingModule } from '@angular/router/testing';
import { AppComponent } from './app.component';

describe('AppComponent', () => {
  let loaderService : LoaderService;
  beforeEach(async () => {
    loaderService = new LoaderService;
    await TestBed.configureTestingModule({
      imports: [
        RouterTestingModule
      ],
      declarations: [
        AppComponent, LoaderComponent
      ],
    }).compileComponents();
  });

  it('should create the app', () => {
    const fixture = TestBed.createComponent(AppComponent);
    const app = fixture.componentInstance;
    expect(app).toBeTruthy();
  });

  it(`should have as title 'WorkstationHubUI'`, () => {
    const fixture = TestBed.createComponent(AppComponent);
    const app = fixture.componentInstance;
    expect(app.title).toEqual('Workstation Hub Service');
  });

  it('Check Is loading hold bool value or not',()=>{
    const fixture = TestBed.createComponent(AppComponent);
    const app = fixture.componentInstance;
    expect(app.isLoading).toBeInstanceOf(Subject<Boolean>);
  });

  it('I able to create instance of the Loader Service instance', ()=>{
    const fixture = TestBed.createComponent(AppComponent);
    const app = fixture.componentInstance;
    expect(loaderService).toBeInstanceOf(LoaderService);
  })
});

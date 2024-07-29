import { ComponentFixture, TestBed } from '@angular/core/testing';

import { TopBarComponent } from './top-bar.component';
import { NgbDropdownModule, NgbModule } from '@ng-bootstrap/ng-bootstrap';
import { FontAwesomeModule } from '@fortawesome/angular-fontawesome';
import { WindowsAuthService } from 'src/app/common/services/windows.auth.service';
import { HttpClient } from '@angular/common/http';
import { HttpClientTestingModule, HttpTestingController } from '@angular/common/http/testing';

describe('TopBarComponent', () => {
  let component: TopBarComponent;
  let fixture: ComponentFixture<TopBarComponent>;
  let httpClient: HttpClient;
  let httpController: HttpTestingController;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [NgbDropdownModule, NgbModule,
        HttpClientTestingModule,
        FontAwesomeModule],
      declarations: [TopBarComponent],
      providers:[WindowsAuthService]
    }).compileComponents();
    httpClient = TestBed.inject(HttpClient);
    httpController = TestBed.inject(HttpTestingController);
    fixture = TestBed.createComponent(TopBarComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

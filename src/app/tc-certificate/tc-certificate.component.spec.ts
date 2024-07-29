import { ComponentFixture, TestBed } from '@angular/core/testing';

import { TcCertificateComponent } from './tc-certificate.component';

describe('TcCertificateComponent', () => {
  let component: TcCertificateComponent;
  let fixture: ComponentFixture<TcCertificateComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ TcCertificateComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(TcCertificateComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
